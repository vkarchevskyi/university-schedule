package notifications

import (
	"encoding/base64"
	"encoding/json"
	"testing"
	"time"
)

func TestTicketValidatorAcceptsSignedUnexpiredTicket(t *testing.T) {
	ticket := testTicket(t, "secret", time.Now().Add(time.Minute).Unix())

	if err := NewTicketValidator("secret").Validate(ticket); err != nil {
		t.Fatalf("expected valid ticket: %v", err)
	}
}

func TestTicketValidatorRejectsInvalidSignature(t *testing.T) {
	ticket := testTicket(t, "secret", time.Now().Add(time.Minute).Unix())

	if err := NewTicketValidator("other-secret").Validate(ticket); err == nil {
		t.Fatal("expected invalid signature to fail")
	}
}

func TestTicketValidatorRejectsExpiredTicket(t *testing.T) {
	ticket := testTicket(t, "secret", time.Now().Add(-time.Minute).Unix())

	if err := NewTicketValidator("secret").Validate(ticket); err == nil {
		t.Fatal("expected expired ticket to fail")
	}
}

func testTicket(t *testing.T, secret string, expiresAt int64) string {
	t.Helper()

	payload, err := json.Marshal(map[string]any{
		"sub":   1,
		"exp":   expiresAt,
		"nonce": "test",
	})
	if err != nil {
		t.Fatal(err)
	}

	encodedPayload := base64.RawURLEncoding.EncodeToString(payload)
	return encodedPayload + "." + sign(encodedPayload, secret)
}
