package di

import (
	"go.uber.org/fx"
	"go.uber.org/zap"

	"github.com/limanmys/core/internal/config"
)

// ConfigModule provides configuration dependency
var ConfigModule = fx.Module("config",
	fx.Provide(NewConfig),
)

// NewConfig creates a new configuration instance
func NewConfig(logger *zap.Logger) (*config.Config, error) {
	logger.Info("Loading configuration...")

	conf, err := config.Load()
	if err != nil {
		logger.Error("Failed to load configuration", zap.Error(err))
		return nil, err
	}

	logger.Info("Configuration loaded successfully",
		zap.String("app_name", conf.App.Name),
		zap.String("env", conf.App.Env),
	)

	return conf, nil
}
