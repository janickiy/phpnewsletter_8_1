<?php

return [
    'str' => '
Q: Is it possible to personalize emails?</strong>
<p>A: Yes, you can. To do this, enter %NAME% in the email template. The next time the emails are sent, it will be replaced with the subscriber\'s name each time.</p>
<strong>Q: Emails to the service\'s mailboxes gmail.com they do not arrive, although they are delivered to the mailboxes of other services. What could be the reason?</strong>
<p>A: Most likely, the address of your mail server is entered by the system gmail.com in
    The blacklist or emails are filtered by an anti-spam filter. Contact your hosting provider.</p>
Q: The mailing list magazine says that 300 emails were sent, but half of them were received. Why?</strong>
<p>A: Try to determine the reason why the emails were not sent. This can be viewed in the non-delivery report, which usually arrives at the email address specified in the settings in the "E-mail" field.
    You or the sending server should receive a non-delivery report with the reasons for non-delivery.</p>
Q: I can\'t send the newsletter through the SMPT server. The following error appears in the mailing list log:
    "The following From address failed: vasya-pupkin@my-domain.com : Called Mail() without being connected"
What is the reason?</strong>
<p>A: There may be several reasons. You may have incorrectly specified the smtp server address or port in the settings.
Another reason may be that access to the SMTP server is blocked by a firewall, or the SMTP server is temporarily unavailable.
    works.</p>
<strong>Q: The image is not displayed in HTML format emails.</strong>
<p>A: Most of the email clients, as well as free email services for the purposes of
By default, security blocks images uploaded from external sources.</p>
Q: What is an SMTP server?</strong>
<p>A: SMTP (Simple Mail Transfer Protocol) is a server on a network, global or
local, that accepts e-mail for further forwarding, as well
as accepts e-mail from other servers for its local users.</p>
<strong>Q: The newsletter magazine does not take into account the number of emails read. Why?</strong>
<p>A: Check the format of outgoing emails in the settings. This option only works if the outgoing HTML format is selected.
    letters.</p>'
];
