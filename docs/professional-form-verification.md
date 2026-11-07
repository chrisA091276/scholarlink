# ADFC professional form and role dashboards

Reference: `docs/reference/ADFC_Form_Professional.docx`, identical to the supplied `C:/Users/Admin/Desktop/scholarship/ADFC_Form_Professional.docx` (SHA-256 `8B72E5543C3583CA9DA0CB383094FC7FB4A6A49AF5A62F800BD6ED3444D8C6D0`).

## Form mapping

| Reference section | Online workflow |
| --- | --- |
| Regional / processing office | Scholarship step |
| Applicant information | Applicant step: names, birth, sex, citizenship, address, mobile, email |
| School information | School step: school/campus, course, year, student ID, school address, disability, sector |
| Family background | Family step: father/mother status, names, addresses |
| Other educational financial assistance | Family step: declaration and conditional program details |
| 2 × 2 photo and documentary requirements | Private document uploads: photo, COR/COE, indigency or declared permitted alternative |
| Qualification, documentary, privacy, consent text | Source text in consent step and downloadable PDF |
| Printed name and date accomplished | Consent step; online acceptance timestamp; signature line for the printed PDF |
| For office use only | Authorized coordinator review, document verification, qualification assessment, reviewer identity and timestamp |

Program-specific academic questions and additional requirements remain available. Alternative income evidence only satisfies the standard requirement when the applicant declares that a Certificate of Indigency cannot be secured.

## Dashboard behavior

- Student: links to individual form steps, evidence status, saved drafts, review status, and correction links to each affected application. Returned applications take priority over newer drafts. A filled consent section does not claim the declaration has been accepted.
- Staff: assigned applications only; separate review/correction and decision stages; required-evidence checklist for each application.
- Administrator: unassigned reviewable applications, pending document verification, decision queue, and active scholarships. Counts match the linked queues, including correction-stage applications.
- Document verification excludes drafts, withdrawn records, final decisions, and applications awaiting administrator decision; those stages do not permit document review. Returned documents have their own filter.
- Saved form names are used in office queues. Account/profile changes do not overwrite the application snapshot.
- Previously submitted records remain readable. Their downloads now use the reference layout with available profile fields mapped without changing saved records or inventing missing answers.

## Verification on 2026-11-05

- Existing PHP suite: 94 tests, 557 assertions passed after the changes.
- Dashboard regression suite: 5 tests, 35 assertions passed, covering role scope, queue/count consistency, correction priority, and conditional document requirements.
- Frontend submission tests: 3 passed.
- Chrome at 1440 px and 390 px: all three dashboards, application sections, office review, PDF download, conditional assistance, direct section links, date display, and ZIP length passed. No JavaScript exceptions, failed requests, external asset requests, or page-width overflow were observed.
- Twelve screenshots and browser results are stored under `storage/app/professional-preview/`; fixture data is in an isolated SQLite database with fictional accounts.

The browser preview uses `php tools/prepare-professional-preview.php`, the local router `tools/professional-preview-router.php` on port 8017, and `node tools/preview-professional-form.cjs`. It does not seed or reset the runtime MySQL database.

Runtime check: XAMPP MySQL was started; all migrations, including `2026_11_05_000001_add_professional_application_form`, were already applied. A fresh private SQL backup was saved under `storage/app/private/backups/mysql/` with no missing local document or avatar files reported. The application is served locally at `http://127.0.0.1:8080`.

## Download layout update

The downloadable PDF follows the reference's A4 margins, navy section headings, shaded field cells, column proportions, source logos, checkbox choices, date format, signature area, and office-use checklist. The financial-assistance declaration begins the second page. A standard completed form fits two pages; additional answers, statements, legacy details, or office notes receive supporting pages when present. Long answers may extend the form to additional pages rather than being truncated.

All three authorized roles use `ApplicationPdfService`. Original JPEG copies of the three logos are stored in `resources/images/adfc-form/`, extracted from the supplied Word document and flattened onto white for PDF rendering without the PHP GD extension. Photo embedding retains the existing JPEG/PNG support and renderer limitations.

`php tools/preview-professional-pdf.php` generates fictional standard, reviewed, and long-answer PDFs in `storage/app/professional-pdf-preview/`. `tools/render-pdf-preview.ps1` renders actual PDF pages through Windows PDF APIs for visual inspection. The standard form was checked at two pages, the reviewed form at three pages including supporting notes, and the long-answer example at five pages. Focused tests cover layout, download authorization, older-record mapping without database changes, escaped custom answers, and returned-document checkbox states.
