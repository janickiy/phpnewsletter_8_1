<?php

return [
    'str' => '
P: ¿Es posible personalizar los correos electrónicos?</strong>
<p>R: Sí, es posible. Para ello, introduzca %NAME% en la plantilla del correo electrónico. La próxima vez que se envíen los correos, este valor será reemplazado por el nombre del suscriptor en cada caso.</p>

<strong>P: Los correos enviados a buzones del servicio gmail.com no llegan, aunque sí se entregan a otros servicios. ¿Cuál podría ser la razón?</strong>
<p>R: Lo más probable es que la dirección de su servidor de correo haya sido incluida en la lista negra del sistema gmail.com o que los correos estén siendo filtrados por un filtro antispam. Póngase en contacto con su proveedor de hosting.</p>

<strong>P: El sistema de boletines indica que se enviaron 300 correos, pero solo se recibieron la mitad. ¿Por qué?</strong>
<p>R: Intente determinar la causa por la cual algunos correos no fueron enviados. Esto puede consultarse en el informe de no entrega, que normalmente se envía a la dirección de correo electrónico especificada en la configuración, en el campo "E-mail".
Usted o el servidor de envío deberían recibir un informe de no entrega con las razones correspondientes.</p>

<strong>P: No puedo enviar el boletín a través del servidor SMTP. Aparece el siguiente error en el registro:
"The following From address failed: example@my-domain.com : Called Mail() without being connected"
¿Cuál es la causa?</strong>
<p>R: Puede haber varias razones. Es posible que haya especificado incorrectamente la dirección o el puerto del servidor SMTP en la configuración.
Otra posible causa es que el acceso al servidor SMTP esté bloqueado por un firewall o que el servidor no esté disponible temporalmente.</p>

<strong>P: Las imágenes no se muestran en los correos en formato HTML.</strong>
<p>R: La mayoría de los clientes de correo electrónico, así como los servicios de correo gratuitos, bloquean por defecto la carga de imágenes desde fuentes externas por razones de seguridad.</p>

<strong>P: ¿Qué es un servidor SMTP?</strong>
<p>R: SMTP (Simple Mail Transfer Protocol) es un servidor en una red, ya sea global o local, que acepta correos electrónicos para su posterior envío, así como correos de otros servidores para sus usuarios locales.</p>

<strong>P: El sistema de boletines no tiene en cuenta el número de correos leídos. ¿Por qué?</strong>
<p>R: Verifique el formato de los correos salientes en la configuración. Esta función solo funciona si se selecciona el formato HTML para los correos enviados.</p>'
];
