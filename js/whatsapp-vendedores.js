jQuery(document).ready(function ($) {
    function registro_de_click() {
         var whatsappUrl = $(this).data('whatsapp-url');
        var vendedorId = $(this).data('vendedor-id');

        // Registrar el clic antes de abrir WhatsApp
        $.post(myAjax.ajaxurl, {
            action: "register_click",
            vendedor_id: vendedorId
        }, function(response) {
            if(response) {
                console.log(response);  // Mensaje del servidor
            } else {
                console.log("Error registrando el click.");
            }
        });

        window.open(whatsappUrl, '_blank');
        e.preventDefault();
    }
    $('#w-btn').on('click',registro_de_click);


});
/*$('#w-btn').on('click', function (e) {
        
        
       
    });

});

jQuery(document).ready(function ($) {

    $('#whatsapp-btn').on('click', function () {
        var whatsappUrl = $(this).data('whatsapp-url');
        var vendedorId = $(this).data('vendedor-id'); 

        // Mostrando las variables en alertas para asegurarnos de que tienen valores.
        console.log('URL de WhatsApp: ' + whatsappUrl);
        console.log('ID del Vendedor: ' + vendedorId);
        console.log('URL del AJAX: ' + myAjax.ajaxurl);

        $.ajax({
            url: myAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'increment_click_counter',
                vendedor_id: vendedorId
            },
            success: function(response) {
                console.log('Respuesta exitosa: ' + JSON.stringify(response));
                if(response.success) {
                    window.open(
                      whatsappUrl,
                      '_blank' // <- This is what makes it open in a new window.
                    );
                    //window.location.href = whatsappUrl; // Redireccionar al usuario a WhatsApp.
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log('Error en la petición AJAX:');
                console.log('jqXHR: ' + JSON.stringify(jqXHR));
                console.log('Text Status: ' + textStatus);
                console.log('Error Thrown: ' + errorThrown);
            },
            complete: function(jqXHR, textStatus) {
                console.log('Petición completada con status: ' + textStatus);
            }
        });
    });
});

jQuery(document).ready(function ($) {

    $('#whatsapp-btn').on('click', function () {
        var whatsappUrl = $(this).data('whatsapp-url');
        var vendedorId = $(this).data('vendedor-id'); // Asegúrate de que cada botón tenga un atributo 'data-vendedor-id' con el ID del vendedor correspondiente.
        alert(whatsappUrl);
        alert(vendedorId);
        alert(myAjax.ajaxurl);
        $.ajax({
            url: myAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'increment_click_counter',
                vendedor_id: vendedorId
            },
            success: function(response) {
                alert(response);
                if(response.success) {
                    window.location.href = whatsappUrl; // Redireccionar al usuario a WhatsApp.
                }
            }
        });
    });
});

    $('#whatsapp-btn').on('click', function() {
        let whatsappUrl = $(this).data('whatsapp-url');
        let vendedorId = $(this).data('vendedor-id');

        $.ajax({
            url: wp_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'increment_click_counter',
                vendedor_id: vendedorId
            },
            success: function(response) {
                if(response.success) {
                    window.location.href = whatsappUrl; // Redireccionar al usuario a WhatsApp.
                }
            }
        });
    });
});
*/