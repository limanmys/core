package di

import (
	"time"

	"go.uber.org/fx"
	"go.uber.org/zap"
	"gorm.io/driver/postgres"
	"gorm.io/gorm"
	gormlogger "gorm.io/gorm/logger"

	"github.com/limanmys/core/internal/config"
)

// DatabaseModule provides database dependency
var DatabaseModule = fx.Module("database",
	fx.Provide(NewDatabase),
)

// NewDatabase creates a new database connection
func NewDatabase(conf *config.Config, logger *zap.Logger) (*gorm.DB, error) {
	logger.Info("Connecting to database...")

	dsn := conf.Database.GetDSN()

	logLevel := gormlogger.Silent
	if conf.Database.Debug {
		logLevel = gormlogger.Info
	}

	db, err := gorm.Open(postgres.Open(dsn), &gorm.Config{
		Logger: gormlogger.Default.LogMode(logLevel),
	})
	if err != nil {
		logger.Error("Failed to connect to database", zap.Error(err))
		return nil, err
	}

	sqlDB, err := db.DB()
	if err != nil {
		logger.Error("Failed to get underlying sql.DB", zap.Error(err))
		return nil, err
	}

	// Configure connection pool
	sqlDB.SetMaxIdleConns(10)
	sqlDB.SetMaxOpenConns(100)
	sqlDB.SetConnMaxLifetime(time.Hour)

	logger.Info("Database connection established successfully")
	return db, nil
}
