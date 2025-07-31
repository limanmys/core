package entities

import (
	"strconv"
)

type User struct {
	Base
	Name     string `json:"name" gorm:"not null;size:100"`
	Username string `json:"username" gorm:"unique;not null;size:50"`
	Password string `json:"password"`
}

// Int64ToString converts int64 to string
func Int64ToString(val int64) string {
	return strconv.FormatInt(val, 10)
}

// IntToString converts int to string
func IntToString(val int) string {
	return strconv.Itoa(val)
}
