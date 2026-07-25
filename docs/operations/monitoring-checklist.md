# Monitoring Checklist

## Continuous
- [ ] Health endpoint `GET /up` monitored externally
- [ ] Platform Monitoring reviewed (`platform.monitoring.index`)
- [ ] Queue depth not sustained-high
- [ ] Failed jobs investigated
- [ ] Disk / DB storage headroom
- [ ] Scheduler heartbeat fresh

## After release (first 24h)
- [ ] Error log rate baseline vs spike
- [ ] Critical user journeys timed
- [ ] No P1 tickets open related to release

## Alert routing
| Condition | Notify |
|-----------|--------|
| Health down | On-call |
| Queue backlog threshold | On-call + Backend |
| Disk >85% | DevOps |