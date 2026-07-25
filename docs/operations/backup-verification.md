# Backup Verification

## Policy (default)
| Item | Frequency | Retention |
|------|-----------|-----------|
| Database dump | Daily | ≥14 days |
| `storage/app` | Daily | ≥14 days |
| Restore drill | Monthly (non-prod) | Log result |

## Daily verification
- [ ] Backup job succeeded (timestamp)
- [ ] Artifact size non-zero / within expected range
- [ ] Offsite / second location copy present (if required)

## Monthly restore drill
- [ ] Restore DB to isolated environment
- [ ] Restore storage sample files
- [ ] App boots; login works; spot-check records
- [ ] Record RTO/RPO observed

**Owner:** ________ **Last drill:** ________ **Result:** ☐ Pass ☐ Fail