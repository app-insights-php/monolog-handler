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
