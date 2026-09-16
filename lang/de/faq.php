<?php

return [
    'str' => '
F: Ist es möglich, E-Mails zu personalisieren?</strong>
<p>A: Ja, das ist möglich. Geben Sie dazu %NAME% in die E-Mail-Vorlage ein. Beim nächsten Versand wird dieser Platzhalter jedes Mal durch den Namen des Abonnenten ersetzt.</p>

<strong>F: E-Mails an Postfächer des Dienstes gmail.com kommen nicht an, obwohl sie an andere Dienste zugestellt werden. Woran kann das liegen?</strong>
<p>A: Höchstwahrscheinlich wurde die Adresse Ihres Mailservers vom System gmail.com auf eine Blacklist gesetzt oder die E-Mails werden durch einen Spamfilter gefiltert. Wenden Sie sich an Ihren Hosting-Anbieter.</p>

<strong>F: Das Newsletter-System zeigt an, dass 300 E-Mails versendet wurden, aber nur die Hälfte wurde empfangen. Warum?</strong>
<p>A: Versuchen Sie die Ursache für die nicht zugestellten E-Mails zu ermitteln. Dies kann im Zustellungsfehlerbericht eingesehen werden, der in der Regel an die in den Einstellungen im Feld "E-Mail" angegebene Adresse gesendet wird.
Sie oder der sendende Server sollten einen Bericht über die Nichtzustellung mit den entsprechenden Gründen erhalten.</p>

<strong>F: Ich kann den Newsletter nicht über den SMTP-Server versenden. Im Versandprotokoll erscheint folgender Fehler:
"The following From address failed: example@my-domain.com : Called Mail() without being connected"
Was ist die Ursache?</strong>
<p>A: Dafür kann es mehrere Gründe geben. Möglicherweise haben Sie die Adresse oder den Port des SMTP-Servers in den Einstellungen falsch angegeben.
Ein weiterer Grund könnte sein, dass der Zugriff auf den SMTP-Server durch eine Firewall blockiert wird oder der Server vorübergehend nicht verfügbar ist.</p>

<strong>F: Bilder werden in HTML-E-Mails nicht angezeigt.</strong>
<p>A: Die meisten E-Mail-Programme sowie kostenlose E-Mail-Dienste blockieren aus Sicherheitsgründen standardmäßig Bilder, die aus externen Quellen geladen werden.</p>

<strong>F: Was ist ein SMTP-Server?</strong>
<p>A: SMTP (Simple Mail Transfer Protocol) ist ein Server in einem Netzwerk – global oder lokal – der E-Mails zum Weiterleiten entgegennimmt und auch E-Mails von anderen Servern für seine lokalen Benutzer empfängt.</p>

<strong>F: Das Newsletter-System berücksichtigt die Anzahl der gelesenen E-Mails nicht. Warum?</strong>
<p>A: Überprüfen Sie das Format der ausgehenden E-Mails in den Einstellungen. Diese Funktion funktioniert nur, wenn das HTML-Format für ausgehende E-Mails ausgewählt ist.</p>'
];
