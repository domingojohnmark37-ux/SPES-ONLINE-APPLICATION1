# Applicant Docs System - Complete Setup & Testing Guide

## What Was Fixed

### 1. ApplicationController.php
**Problem**: Applications weren't being displayed in the admin Applicant Docs page.

**Solution**:
- Fixed `applicantDocs()` method to use proper MySQL DISTINCT query: `selectRaw('DISTINCT applicant_name, applicant_email')`
- Enhanced `getApplicantFiles()` to:
  - Return both uploaded files AND form data
  - Include application status in response
  - Filter for applications with either files OR form_data

### 2. applicant-docs.blade.php (Admin View)
**Problem**: Records not showing, hardcoded placeholder text, file/form view buttons not functional.

**Solution**:
- Removed hardcoded "Gibson Sab" placeholder
- Updated JavaScript to handle:
  - Applications with uploaded files
  - Applications with form data only (no files)
  - Show "Application Form Submission" entry when form exists
  - Display status badge (pending/approved/denied)
- Added "Status" column to track application status
- Fixed View/Download button routing

## How It Works Now

### User Workflow
1. User logs in and goes to `/apply`
2. Completes the online SPES application with:
   - Personal info (name, birth date, address, etc.)
   - Contact details
   - Optional file attachments
3. Submits the form
4. Application is created with:
   - `status = 'pending'` (default)
   - `form_data` = all form fields
   - `files` = array of uploaded file paths (if any)

### Admin Workflow
1. Admin goes to Admin Dashboard → Applicant Docs
2. Left sidebar shows list of applicants who submitted
3. Click on applicant name to load their applications
4. Table shows:
   - **Individual Files**: Each uploaded file as separate row
   - **Form Submission**: Entry for the application form itself
   - **Status**: Shows current application status
5. Actions:
   - **View**: Opens the complete filled form (view-form.blade.php)
   - **Download**: Downloads the specific uploaded file

## Data Structure

### Applications Table
```
id, ref_id, user_id, applicant_name, applicant_email, 
files (JSON array), form_data (JSON object), status, timestamps
```

### Stored File Path
- **Location**: `storage/app/public/applications/`
- **Format**: Auto-generated filename
- **Access**: `/storage/applications/{filename}`

### Form Data (JSON)
```json
{
  "surname": "Domingo",
  "first_name": "John Mark",
  "middle_name": "Santos",
  "birth_date": "1990-05-15",
  "birth_place": "Manila",
  "citizenship": "Filipino",
  "phone": "09999999999",
  "email": "user@example.com",
  "social_media": "@username",
  "status": ["Single"],
  "gsis_beneficiary": "None"
}
```

## Routes

| Route | Method | Purpose |
|-------|--------|---------|
| /admin/applicant-docs | GET | View all applicants |
| /admin/applicant-files | GET | AJAX: Get applicant's files/forms |
| /admin/applications/{id}/view-form | GET | View submitted form |
| /admin/applications/{id}/export | GET | Export form as PDF |

## Testing Checklist

### 1. Form Submission
- [ ] User can access `/apply`
- [ ] Form accepts all fields
- [ ] File upload is optional
- [ ] Form submits successfully
- [ ] Redirects to `/my-applications`

### 2. Admin Applicant Docs
- [ ] Admin can access `/admin/applicant-docs`
- [ ] Applicant name appears in left sidebar after submission
- [ ] File count shows correctly (1 file, 2 files, etc.)
- [ ] Clicking applicant name loads their data

### 3. File/Form Display
- [ ] Table shows both uploaded files AND form submission entry
- [ ] Status column displays correctly
- [ ] "View" button opens form in new view
- [ ] "Download" button downloads the file
- [ ] Search/filter works for filenames

### 4. View Form
- [ ] Form displays all submitted data
- [ ] Print button works
- [ ] Back button returns to docs list
- [ ] Displays properly for both screen and print

## Common Issues & Solutions

### Issue: No applicants showing in list
**Solution**: Make sure applications have been submitted. Check database:
```sql
SELECT COUNT(*) FROM applications;
SELECT applicant_name, applicant_email, status FROM applications;
```

### Issue: Files not downloading
**Solution**: 
- Verify `storage/app/public/` is accessible
- Run: `php artisan storage:link` (if not already done)
- Check file paths in database are correct

### Issue: Form not showing in View
**Solution**:
- Verify `form_data` column has JSON data
- Check `view-form.blade.php` has corresponding fields
- Ensure application has `form_data` (not NULL)

## Future Enhancements
- [ ] Bulk file download as ZIP
- [ ] Application status update from admin panel
- [ ] File preview (PDF, images)
- [ ] Search/filter by applicant name
- [ ] Export all records to Excel
