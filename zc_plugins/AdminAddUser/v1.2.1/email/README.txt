This folder holds an optional header banner image for the welcome/activation email this
plugin sends. It's entirely optional - if no file is here, the email is sent without one,
exactly as before.

The easiest way to set this is the "E-Mail Header to Import:" field on the admin's Add
Customers page (below the Bulk Upload area) - it handles everything below automatically,
including replacing whatever was here before.

To place a file here directly instead (e.g. via FTP), name it exactly:

  header.jpg   (or header.jpeg, header.png, header.gif, header.webp)

Only the first matching file found (checked in that order) is used. Zen Cart core's own
default email header image (images/header.jpg) is 550 x 110 at 72 DPI - matching that size is
recommended, but not required or validated: whatever you supply is displayed at a fixed
width of 550px (height scales automatically), the same way core's own header image is shown.

The image needs to be reachable at a normal, public URL (this folder is not hidden or
access-restricted) - that's how email clients load it, the same way any email header/logo
image works. It is only ever used in the HTML version of the email; plain-text recipients see
no difference.
