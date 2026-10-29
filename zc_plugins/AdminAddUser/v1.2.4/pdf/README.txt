This folder holds the two printable sign-up form PDFs that the "Print Sign-Up Form" links
in the admin's Bulk Upload area serve. Both ship with the plugin and work as soon as it is
installed - there is nothing to add here to get them working:

  signup_form.pdf
  signup_form_wholesale.pdf

Zen Cart core ships no PDF-generation library, so these are static files rather than
something this plugin builds on the fly. That also means you can replace either one with
your own version - a sheet carrying your logo, say, or one with your own columns - simply by
overwriting it here with a file of the same name. Nothing else needs changing.

How the shipped pair are laid out, so a replacement can match:
  - Landscape orientation.
  - The only heading on the page is "Please Print".
  - A multi-row roster/sign-in-sheet layout - one blank line per person, not one form per
    page - with columns: First Name | Last Name | Email | Phone.
  - signup_form_wholesale.pdf additionally has a "Wholesale?" Yes/No checkbox column.
  - No address fields - those are collected later, when the customer completes their profile
    via the emailed activation link (see the main readme's "How Activation Works" section).

The wholesale sheet's link only appears when Configuration->My Store->Wholesale Pricing is
enabled, so a store not using wholesale never sees it.

If either file is removed, its link shows an on-page notice instead of downloading anything,
rather than serving a broken file.
