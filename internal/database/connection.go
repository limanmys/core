package database

import (
	"sync"

	"gorm.io/gorm"
)

var (
	instance *gorm.DB
	once     sync.Once
)

// SetConnection sets the global database connection
func SetConnection(db *gorm.DB) {
	once.Do(func() {
		instance = db
	})
}

// GetConnection returns the global database connection
func GetConnection() *gorm.DB {
	return instance
}
