package di

import (
	"go.uber.org/fx"
	"go.uber.org/zap"
	"gorm.io/gorm"

	"github.com/limanmys/core/internal/config"
	"github.com/limanmys/core/internal/migration"
)

// MigrationModule provides migration service
var MigrationModule = fx.Module("migration",
	fx.Provide(NewMigrationService),
)

// NewMigrationService creates a new migration service
func NewMigrationService(db *gorm.DB, conf *config.Config, logger *zap.Logger) *migration.Service {
	return migration.NewService(db, conf, logger)
}
