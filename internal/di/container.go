package di

import (
	"go.uber.org/fx"
)

// Container holds all the DI modules
var Container = fx.Options(
	LoggerModule,
	ConfigModule,
	DatabaseModule,
	RedisModule,
	ServerModule,
	MigrationModule,
	RepositoryModule,
	ServiceModule,
	UserControllerModule,
	RouterModule,
	ApplicationModule,
)
