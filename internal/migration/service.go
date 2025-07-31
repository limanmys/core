package migration

import (
	"go.uber.org/zap"
	"gorm.io/gorm"

	"github.com/limanmys/core/app/entities"
	"github.com/limanmys/core/internal/config"
)

// Service handles database migrations using GORM AutoMigrate
type Service struct {
	db     *gorm.DB
	config *config.Config
	logger *zap.Logger
}

// NewService creates a new migration service
func NewService(db *gorm.DB, conf *config.Config, logger *zap.Logger) *Service {
	return &Service{
		db:     db,
		config: conf,
		logger: logger,
	}
}

// AutoMigrate runs GORM auto migration for all models
func (s *Service) AutoMigrate() error {
	s.logger.Info("Running database auto migration...")

	// Define all models to migrate
	models := []interface{}{
		&entities.User{},
		// Add other models here as they are created
	}

	// Run auto migration
	if err := s.db.AutoMigrate(models...); err != nil {
		s.logger.Error("Auto migration failed", zap.Error(err))
		return err
	}

	s.logger.Info("Database auto migration completed successfully")
	return nil
}

// GetDB returns the database instance
func (s *Service) GetDB() *gorm.DB {
	return s.db
}
