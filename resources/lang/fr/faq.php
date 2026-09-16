<?php

return [
    'str' => '
Q : Est-il possible de personnaliser les e-mails ?</strong>
<p>R : Oui, c\'est possible. Pour cela, saisissez %NAME% dans le modèle d\'e-mail. Lors de l\'envoi des messages, cette valeur sera remplacée par le nom de l\'abonné.</p>
<strong>Q : Les e-mails envoyés vers les boîtes gmail.com ne sont pas reçus, bien qu\'ils arrivent sur d\'autres services. Quelle peut en être la raison ?</strong>
<p>R : Très probablement, l\'adresse de votre serveur de messagerie a été ajoutée par gmail.com à une liste noire, ou les e-mails sont filtrés par un filtre anti-spam. Veuillez contacter votre hébergeur.</p>
Q : Le journal indique que 300 e-mails ont été envoyés, mais seulement la moitié a été reçue. Pourquoi ?</strong>
<p>R : Essayez de déterminer la raison pour laquelle les e-mails n\'ont pas été délivrés. Cela peut être consulté dans le rapport de non-distribution, généralement envoyé à l\'adresse e-mail indiquée dans les paramètres (champ « E-mail »).
    Vous ou le serveur d\'envoi devriez recevoir un rapport de non-distribution avec les motifs correspondants.</p>
Q : Je ne peux pas envoyer la newsletter via le serveur SMTP. L\'erreur suivante apparaît dans le journal :
    "The following From address failed: example@my-domain.com : Called Mail() without being connected"
Quelle en est la raison ?</strong>
<p>R : Plusieurs causes sont possibles. Il se peut que l\'adresse du serveur SMTP ou le port ait été mal configuré dans les paramètres.
Une autre cause peut être que l\'accès au serveur SMTP est bloqué par un pare-feu, ou que le serveur SMTP est temporairement indisponible.</p>
<strong>Q : Les images ne s\'affichent pas dans les e-mails au format HTML.</strong>
<p>R : La plupart des clients de messagerie, ainsi que les services de messagerie gratuits, bloquent par défaut le chargement des images provenant de sources externes pour des raisons de sécurité.</p>
Q : Qu\'est-ce qu\'un serveur SMTP ?</strong>
<p>R : SMTP (Simple Mail Transfer Protocol) est un serveur sur un réseau, global ou local, qui accepte les e-mails pour leur transmission ultérieure, ainsi que les e-mails provenant d\'autres serveurs pour ses utilisateurs locaux.</p>
<strong>Q : La newsletter ne comptabilise pas le nombre d\'e-mails lus. Pourquoi ?</strong>
<p>R : Vérifiez le format des e-mails sortants dans les paramètres. Cette option ne fonctionne que si le format HTML est sélectionné.</p>'
];
