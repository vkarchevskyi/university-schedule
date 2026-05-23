package main

import (
	"net/http"
	"net/http/httptest"
	"strings"
	"testing"

	"github.com/vkarchevskyi/university-schedule/services/schedule/internal/validation"
)

func TestValidateScheduleHandlerRejectsMalformedJSON(t *testing.T) {
	response := httptest.NewRecorder()
	request := httptest.NewRequest(http.MethodPost, "/validate-schedule", strings.NewReader("{"))

	validateScheduleHandler(validation.NewValidator(), nil).ServeHTTP(response, request)

	if response.Code != http.StatusBadRequest {
		t.Fatalf("status = %d, want %d", response.Code, http.StatusBadRequest)
	}
}

func TestValidateScheduleHandlerRejectsMissingScheduleInput(t *testing.T) {
	response := httptest.NewRecorder()
	request := httptest.NewRequest(http.MethodPost, "/validate-schedule", strings.NewReader(`{}`))

	validateScheduleHandler(validation.NewValidator(), nil).ServeHTTP(response, request)

	if response.Code != http.StatusBadRequest {
		t.Fatalf("status = %d, want %d", response.Code, http.StatusBadRequest)
	}
}

func TestValidateScheduleHandlerValidatesInlineSchedule(t *testing.T) {
	response := httptest.NewRecorder()
	request := httptest.NewRequest(http.MethodPost, "/validate-schedule", strings.NewReader(`{"schedule":{"entries":[],"teachingLoads":[],"teacherSubjectAssignments":[],"teacherUnavailabilityRules":[]}}`))

	validateScheduleHandler(validation.NewValidator(), nil).ServeHTTP(response, request)

	if response.Code != http.StatusOK {
		t.Fatalf("status = %d, want %d", response.Code, http.StatusOK)
	}
	if !strings.Contains(response.Body.String(), `"valid":true`) {
		t.Fatalf("response body = %q, want valid true", response.Body.String())
	}
}

func TestValidateScheduleHandlerReportsUnavailableStoreForScheduleID(t *testing.T) {
	response := httptest.NewRecorder()
	request := httptest.NewRequest(http.MethodPost, "/validate-schedule", strings.NewReader(`{"scheduleId":1}`))

	validateScheduleHandler(validation.NewValidator(), nil).ServeHTTP(response, request)

	if response.Code != http.StatusServiceUnavailable {
		t.Fatalf("status = %d, want %d", response.Code, http.StatusServiceUnavailable)
	}
}
