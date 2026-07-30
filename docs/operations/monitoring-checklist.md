# Monitoring Checklist

## Continuous
- [ ] Health endpoint `GET /up` monitored externally
- [ ] Platform Monitoring reviewed (`platform.monitoring.index`)
- [ ] Queue depth not sustained-high
- [ ] Failed jobs investigated
- [ ] All configured queue workers live; queue canary timestamp recent after deploy
- [ ] Disk / DB storage headroom
- [ ] Scheduler heartbeat key `platform.scheduler.last_run` newer than `SCHEDULER_STALE_AFTER`
- [ ] Queue and scheduler cron/Supervisor logs writable and rotating

## After release (first 24h)
- [ ] Error log rate baseline vs spike
- [ ] Critical user journeys timed
- [ ] No P1 tickets open related to release

## Alert routing
| Condition | Notify |
|-----------|--------|
| Health down | On-call |
| Queue backlog threshold | On-call + Backend |
| Queue canary missing / worker stale | On-call + Backend |
| Scheduler heartbeat stale | On-call + DevOps |
| Disk >85% | DevOps |

See [Queues and Scheduler — Release 1.2.x](../deployment/queues-and-scheduler.md) for validation and recovery.