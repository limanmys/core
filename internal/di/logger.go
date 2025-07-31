package di

import (
	"go.uber.org/fx"
	"go.uber.org/zap"

	"github.com/limanmys/core/internal/config"
)

// LoggerModule provides logger dependency
var LoggerModule = fx.Module("logger",
	fx.Provide(NewLogger),
)

// NewLogger creates a new zap logger based on environment
func NewLogger(conf *config.Config) *zap.Logger {
	if conf.App.Env == "production" {
		logger, _ := zap.NewProduction()
		return logger
	}

	logger, _ := zap.NewDevelopment()
	return logger
}

// NewProductionLogger creates a production logger
func NewProductionLogger() *zap.Logger {
	logger, _ := zap.NewProduction()
	return logger
}

// NewDevelopmentLogger creates a development logger
func NewDevelopmentLogger() *zap.Logger {
	logger, _ := zap.NewDevelopment()
	return logger
}
