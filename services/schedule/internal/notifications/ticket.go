package notifications

import (
	"crypto/hmac"
	"crypto/sha256"
	"encoding/base64"
	"encoding/json"
	"errors"
	"strings"
	"time"
)

var ErrInvalidTicket = errors.New("invalid websocket ticket")

type TicketValidator struct {
	secret string
}

func NewTicketValidator(secret string) TicketValidator {
	return TicketValidator{secret: secret}
}

func (validator TicketValidator) Validate(ticket string) error {
	parts := strings.Split(ticket, ".")
	if len(parts) != 2 || validator.secret == "" {
		return ErrInvalidTicket
	}

	expected := sign(parts[0], validator.secret)
	if !hmac.Equal([]byte(expected), []byte(parts[1])) {
		return ErrInvalidTicket
	}

	payloadBytes, err := base64.RawURLEncoding.DecodeString(parts[0])
	if err != nil {
		return ErrInvalidTicket
	}

	var payload struct {
		ExpiresAt int64 `json:"exp"`
	}
	if err := json.Unmarshal(payloadBytes, &payload); err != nil {
		return ErrInvalidTicket
	}
	if payload.ExpiresAt <= time.Now().Unix() {
		return ErrInvalidTicket
	}

	return nil
}

func sign(payload string, secret string) string {
	mac := hmac.New(sha256.New, []byte(secret))
	_, _ = mac.Write([]byte(payload))

	return base64.RawURLEncoding.EncodeToString(mac.Sum(nil))
}
