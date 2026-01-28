package handler

import (
	"encoding/json"
	"net/http"
)

type HelloResponse struct {
	Message string `json:"message"`
}

func Hello(w http.ResponseWriter, r *http.Request) {
	resp := HelloResponse{
		Message: "Hello from Go Backend!",
	}

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(resp)
}
