# Microsoft App Insights monolog handler

It provides to monolog handlers for AppInsights: for tracking dependency (AppInsightsDependencyHandler) and traces (AppInsightsTraceHandler).

### Long running processes

It might be tricky to find a proper moment to flush everything to AppInsights for long running processes (e.g: consumers). 
One of the solution might be a [Buffer Handler](https://github.com/Seldaek/monolog/blob/master/src/Monolog/Handler/BufferHandler.php).
You can easily wrap both handlers by Buffer Handler and set up an overflow buffer. They are designed to flush everything into AppInsight's
when a buffer overflows. 

### Limitations

Size of the telemetry is limited to [64 kilobytes](https://docs.microsoft.com/en-us/azure/azure-monitor/service-limits#application-insights).
Handlers checks the length of the telemetry before adding it to the AppInsights Client's queue. If it exceeds the limit
__it won't be added!__ It means that AppInsights should not be the only source for your data. You should always have a copy somewhere else
where you don't have such limitations.

### Usage example in Laravel:

1. Update config/logging.php with a new entry:

```
'appinsights' => [
            'driver' => 'monolog',
            'level' => 'debug',
            'handler' => \AppInsightsPHP\Monolog\Handler\AppInsightsTraceHandler::class,
            'formatter' => \AppInsightsPHP\Monolog\Formatter\ContextFlatterFormatter::class
        ],
```

2. Register a new service provider
```
php artisan make:provider AppInsightsLogProvider
```

2.1 Register servicer provider dependencies for the log handler in the `boot()` method.
```
/**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->bind(
            AppInsightsTraceHandler::class,
            function ($app) {
                $telemetryClient = new \ApplicationInsights\Telemetry_Client();
                $context = $telemetryClient->getContext();
                $context->setInstrumentationKey(env('APPINSIGHTS_INSTRUMENTATIONKEY'));

                /** @var CacheManager $cacheManager */
                $failureCache = new FailureCache(Cache::repository(new NullStore()));

                /** @var Logger $defaultLogger */
                $defaultLogger = Log::getFacadeRoot();

                $client = new Client($telemetryClient, Configuration::createDefault(), $failureCache, $defaultLogger);

                $handler = new AppInsightsTraceHandler($client);

                return $handler;
            }
        );
```
