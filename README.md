# Moomba Academic System V5

This update integrates the standalone results-check/report logic into the main native PHP system.

## New in V5
- upgraded results engine
- single-exam or term-summary report cards
- best-six subject logic with ENG inclusion
- combined science logic for selected classes (`10AB`, `11AB`, `12BC`, `10DT`, `11DT`, `12DT`)
- automatic remarks on report cards
- public quick-results page now supports student code, email, or phone lookup
- printable/downloadable report cards from both portal and public results page
- class ranking included on result cards
- cleaner report layout for phone and desktop

## Important
Set your correct base URL in `config/config.php`, for example:

```php
'base_url' => 'http://localhost/school',
```

Then open:

```text
http://localhost/school/login
```

Public quick results:

```text
http://localhost/school/results/quick
```

## Notes
- PDF download still uses browser-side libraries from CDN.
- Existing database users remain supported.
- This update keeps the system academic-only.


## V6.2 Exact Report Styling Patch
- report card styling now follows the uploaded generate_report.php and styles.css much more closely
- exact centered header, circular logos, table styling, summary block, and grading legend
- Services namespace autoload fix remains included


V6.3 update: report card PDF now uses jsPDF + autoTable logic based on the HTML report design, with dynamic logos, signature, grading table, and exact table export from the report card view.


V6.6 update:
- report card logos, signature, ministry line, school name, motto, contacts, and signature label are fully controlled in Admin Settings
- analytics upgraded with school-wide/class-wise/subject-wise views, teacher rankings, class leaders, grade distribution, and charts
