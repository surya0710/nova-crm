# Recruitment Troubleshooting

## Purpose
Resolve common issues in the recruitment foundation module.

## Opening creation blocked
**Symptom:** Validation error on requisition status.  
**Cause:** Opening created from non-approved requisition.  
**Fix:** Approve the requisition first.

## Duplicate candidate email
**Symptom:** Email uniqueness validation failure.  
**Cause:** Candidate already exists in the organization.  
**Fix:** Use the existing candidate profile and create a new application.

## Application rejected for opening
**Symptom:** Service error on application create.  
**Cause:** Opening is not published.  
**Fix:** Publish the opening before accepting applications.

## Missing sidebar links
**Symptom:** Recruitment menu not visible.  
**Cause:** User lacks `recruitment.view`.  
**Fix:** Assign recruitment permissions via organization roles.

## Notifications not received
**Symptom:** No in-app notification.  
**Cause:** Recipient is not an organization member or hiring manager has no linked user.  
**Fix:** Link employee to user account or assign a recruiter on the application.
