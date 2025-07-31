package seeds

import (
	"golang.org/x/crypto/bcrypt"
	"gorm.io/gorm"

	"github.com/limanmys/core/app/entities"
)

func Init(db *gorm.DB) error {
	var existingUser entities.User
	err := db.Where("username = ?", "liman").First(&existingUser).Error
	if err != nil && err != gorm.ErrRecordNotFound {
		return err
	}

	// If user already exists, skip seeding
	if err != gorm.ErrRecordNotFound {
		return nil
	}

	// Create admin user
	hashed, err := bcrypt.GenerateFromPassword([]byte("liman"), 14)
	if err != nil {
		return err
	}

	return db.Create(&entities.User{
		Name:     "Admin",
		Username: "liman",
		Password: string(hashed),
	}).Error
}
