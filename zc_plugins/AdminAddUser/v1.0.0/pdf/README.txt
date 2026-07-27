This folder holds the two printable sign-up form PDFs that the "Print Sign-Up Form" links
in the admin's Bulk area serve. Zen Cart core doesn't ship a PDF-generation library, so these
are static files the store owner supplies - not something this plugin generates.

Add exactly these two files here before deploying:

  signup_form.pdf
  signup_form_wholesale.pdf

Agreed design for both:
  - Landscape orientation.
  - The only heading on the page is "Please Print".
  - A multi-row roster/sign-in-sheet layout - one blank line per person, not one form per
    page - with columns: First Name | Last Name | Email | Phone.
  - signup_form_wholesale.pdf additionally has a "Wholesale?" Yes/No checkbox column.
  - No address fields - those are collected later, when the customer completes their profile
    via the emailed activation link (see the main readme's "How Activation Works" section).

Until both files are present, clicking either "Print Sign-Up Form" link in the admin shows an
on-page notice instead of downloading anything.
