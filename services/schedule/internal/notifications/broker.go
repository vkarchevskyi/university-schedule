package notifications

import (
	"bufio"
	"context"
	"crypto/sha1"
	"encoding/base64"
	"encoding/binary"
	"encoding/json"
	"errors"
	"fmt"
	"io"
	"log"
	"net"
	"net/http"
	"strings"
	"sync"

	amqp "github.com/rabbitmq/amqp091-go"
)

type Broker struct {
	validator TicketValidator
	clients   map[*client]struct{}
	mu        sync.Mutex
}

func NewBroker(validator TicketValidator) *Broker {
	return &Broker{
		validator: validator,
		clients:   make(map[*client]struct{}),
	}
}

func (broker *Broker) ServeHTTP(writer http.ResponseWriter, request *http.Request) {
	if err := broker.validator.Validate(request.URL.Query().Get("ticket")); err != nil {
		http.Error(writer, "invalid websocket ticket", http.StatusUnauthorized)
		return
	}

	connection, reader, err := upgrade(writer, request)
	if err != nil {
		http.Error(writer, "websocket upgrade failed", http.StatusBadRequest)
		return
	}

	client := newClient(connection, reader, broker)
	broker.add(client)
	client.run()
}

func (broker *Broker) StartRabbitMQConsumer(ctx context.Context, rabbitmqURL string, queueName string) error {
	if queueName == "" {
		queueName = DefaultQueueName
	}

	connection, err := amqp.Dial(rabbitmqURL)
	if err != nil {
		return fmt.Errorf("connect rabbitmq notifications: %w", err)
	}
	defer connection.Close()

	channel, err := connection.Channel()
	if err != nil {
		return fmt.Errorf("open notification channel: %w", err)
	}
	defer channel.Close()

	queue, err := channel.QueueDeclare(queueName, false, false, false, false, nil)
	if err != nil {
		return fmt.Errorf("declare notification queue: %w", err)
	}

	deliveries, err := channel.Consume(queue.Name, "", true, false, false, false, nil)
	if err != nil {
		return fmt.Errorf("consume notification queue: %w", err)
	}

	log.Printf("Web notification broker consuming queue %s", queue.Name)
	for {
		select {
		case <-ctx.Done():
			return ctx.Err()
		case delivery, ok := <-deliveries:
			if !ok {
				return errors.New("notification queue deliveries closed")
			}

			var event Event
			if err := json.Unmarshal(delivery.Body, &event); err != nil {
				log.Printf("decode notification event: %v", err)
				continue
			}
			broker.publish(event)
		}
	}
}

func (broker *Broker) add(client *client) {
	broker.mu.Lock()
	defer broker.mu.Unlock()
	broker.clients[client] = struct{}{}
}

func (broker *Broker) remove(client *client) {
	broker.mu.Lock()
	defer broker.mu.Unlock()
	delete(broker.clients, client)
}

func (broker *Broker) publish(event Event) {
	broker.mu.Lock()
	clients := make([]*client, 0, len(broker.clients))
	for client := range broker.clients {
		if client.subscribedTo(event.Type, event.JobID) {
			clients = append(clients, client)
		}
	}
	broker.mu.Unlock()

	for _, client := range clients {
		client.send(event)
	}
}

type subscription struct {
	Action string `json:"action"`
	Type   string `json:"type"`
	JobID  string `json:"jobId"`
}

type client struct {
	connection     net.Conn
	reader         *bufio.Reader
	broker         *Broker
	subscriptions  map[string]struct{}
	subscriptionMu sync.Mutex
	writeMu        sync.Mutex
}

func newClient(connection net.Conn, reader *bufio.Reader, broker *Broker) *client {
	return &client{
		connection:    connection,
		reader:        reader,
		broker:        broker,
		subscriptions: make(map[string]struct{}),
	}
}

func (client *client) run() {
	defer func() {
		client.broker.remove(client)
		_ = client.connection.Close()
	}()

	for {
		payload, err := readTextFrame(client.reader)
		if err != nil {
			return
		}

		var message subscription
		if err := json.Unmarshal(payload, &message); err != nil {
			continue
		}
		if message.Action == "subscribe" && message.Type != "" && message.JobID != "" {
			client.subscriptionMu.Lock()
			client.subscriptions[subscriptionKey(message.Type, message.JobID)] = struct{}{}
			client.subscriptionMu.Unlock()
		}
		if message.Action == "unsubscribe" {
			client.subscriptionMu.Lock()
			delete(client.subscriptions, subscriptionKey(message.Type, message.JobID))
			client.subscriptionMu.Unlock()
		}
	}
}

func (client *client) subscribedTo(eventType string, jobID string) bool {
	client.subscriptionMu.Lock()
	defer client.subscriptionMu.Unlock()
	_, ok := client.subscriptions[subscriptionKey(eventType, jobID)]
	return ok
}

func (client *client) send(event Event) {
	payload, err := json.Marshal(event)
	if err != nil {
		return
	}

	client.writeMu.Lock()
	defer client.writeMu.Unlock()
	if err := writeTextFrame(client.connection, payload); err != nil {
		client.broker.remove(client)
		_ = client.connection.Close()
	}
}

func subscriptionKey(eventType string, jobID string) string {
	return eventType + ":" + jobID
}

func upgrade(writer http.ResponseWriter, request *http.Request) (net.Conn, *bufio.Reader, error) {
	if !strings.EqualFold(request.Header.Get("Upgrade"), "websocket") {
		return nil, nil, errors.New("missing websocket upgrade")
	}

	key := request.Header.Get("Sec-WebSocket-Key")
	if key == "" {
		return nil, nil, errors.New("missing websocket key")
	}

	hijacker, ok := writer.(http.Hijacker)
	if !ok {
		return nil, nil, errors.New("websocket hijacking unsupported")
	}

	connection, reader, err := hijacker.Hijack()
	if err != nil {
		return nil, nil, err
	}

	response := "HTTP/1.1 101 Switching Protocols\r\n" +
		"Upgrade: websocket\r\n" +
		"Connection: Upgrade\r\n" +
		"Sec-WebSocket-Accept: " + websocketAccept(key) + "\r\n\r\n"
	if _, err := connection.Write([]byte(response)); err != nil {
		_ = connection.Close()
		return nil, nil, err
	}

	return connection, reader.Reader, nil
}

func websocketAccept(key string) string {
	hash := sha1.Sum([]byte(key + "258EAFA5-E914-47DA-95CA-C5AB0DC85B11"))
	return base64.StdEncoding.EncodeToString(hash[:])
}

func readTextFrame(reader *bufio.Reader) ([]byte, error) {
	header := make([]byte, 2)
	if _, err := io.ReadFull(reader, header); err != nil {
		return nil, err
	}

	opcode := header[0] & 0x0f
	if opcode == 0x8 {
		return nil, io.EOF
	}
	if opcode != 0x1 {
		return nil, errors.New("unsupported websocket frame")
	}

	masked := header[1]&0x80 != 0
	length := int64(header[1] & 0x7f)
	switch length {
	case 126:
		extended := make([]byte, 2)
		if _, err := io.ReadFull(reader, extended); err != nil {
			return nil, err
		}
		length = int64(binary.BigEndian.Uint16(extended))
	case 127:
		extended := make([]byte, 8)
		if _, err := io.ReadFull(reader, extended); err != nil {
			return nil, err
		}
		length = int64(binary.BigEndian.Uint64(extended))
	}

	mask := make([]byte, 4)
	if masked {
		if _, err := io.ReadFull(reader, mask); err != nil {
			return nil, err
		}
	}

	payload := make([]byte, length)
	if _, err := io.ReadFull(reader, payload); err != nil {
		return nil, err
	}
	if masked {
		for index := range payload {
			payload[index] ^= mask[index%4]
		}
	}

	return payload, nil
}

func writeTextFrame(writer io.Writer, payload []byte) error {
	header := []byte{0x81}
	length := len(payload)
	switch {
	case length < 126:
		header = append(header, byte(length))
	case length <= 65535:
		header = append(header, 126, byte(length>>8), byte(length))
	default:
		header = append(header, 127, 0, 0, 0, 0, byte(length>>24), byte(length>>16), byte(length>>8), byte(length))
	}

	if _, err := writer.Write(header); err != nil {
		return err
	}
	_, err := writer.Write(payload)
	return err
}
