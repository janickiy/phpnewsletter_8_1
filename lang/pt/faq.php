<?php

return [
    'str' => '
Q: É possível personalizar os e-mails?</strong>
<p>A: Sim, é possível. Para isso, insira %NAME% no modelo de e-mail. Na próxima vez que os e-mails forem enviados, ele será substituído pelo nome do assinante a cada envio.</p>

<strong>Q: Os e-mails enviados para caixas do serviço gmail.com não chegam, embora sejam entregues a outros serviços. Qual pode ser o motivo?</strong>
<p>A: Muito provavelmente, o endereço do seu servidor de e-mail foi incluído na lista negra do sistema gmail.com ou os e-mails estão sendo filtrados por um filtro anti-spam. Entre em contato com o seu provedor de hospedagem.</p>

Q: O relatório de envio indica que 300 e-mails foram enviados, mas apenas metade foi recebida. Por quê?</strong>
<p>A: Tente identificar o motivo pelo qual os e-mails não foram entregues. Isso pode ser verificado no relatório de não entrega, que normalmente é enviado para o endereço especificado nas configurações no campo "E-mail".
Você ou o servidor de envio devem receber um relatório de não entrega com os motivos da falha.</p>

Q: Não consigo enviar a newsletter através do servidor SMTP. O seguinte erro aparece no log:
"The following From address failed: example@my-domain.com : Called Mail() without being connected"
Qual é o motivo?</strong>
<p>A: Pode haver várias razões. Você pode ter especificado incorretamente o endereço ou a porta do servidor SMTP nas configurações.
Outra possibilidade é que o acesso ao servidor SMTP esteja bloqueado por um firewall, ou que o servidor SMTP esteja temporariamente indisponível.</p>

<strong>Q: As imagens não são exibidas em e-mails no formato HTML.</strong>
<p>A: A maioria dos clientes de e-mail, assim como os serviços gratuitos, por razões de segurança, bloqueia por padrão imagens carregadas de fontes externas.</p>

Q: O que é um servidor SMTP?</strong>
<p>A: SMTP (Simple Mail Transfer Protocol) é um servidor em uma rede, global ou local, que recebe e-mails para encaminhamento posterior, bem como recebe e-mails de outros servidores para seus usuários locais.</p>

<strong>Q: O relatório da newsletter não contabiliza os e-mails lidos. Por quê?</strong>
<p>A: Verifique o formato dos e-mails enviados nas configurações. Esta funcionalidade funciona apenas se o formato HTML estiver selecionado.</p>'
];
