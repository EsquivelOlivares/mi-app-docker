// public/js/app.js - JavaScript personalizado

$(document).ready(function() {
    console.log('Mi App Docker - Bootstrap + jQuery cargado');
    
    // Inicializar tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Inicializar popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Confirmación antes de acciones importantes
    $('.confirm-action').on('click', function(e) {
        if (!confirm($(this).data('confirm') || '¿Estás seguro?')) {
            e.preventDefault();
            return false;
        }
    });
    
    // Formularios con validación
    $('form.needs-validation').on('submit', function(e) {
        if (!this.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
        }
        $(this).addClass('was-validated');
    });
    
    // Auto-dismiss alerts después de 5 segundos
    setTimeout(function() {
        $('.alert:not(.alert-permanent)').alert('close');
    }, 5000);
    
    // Actualizar contador de carrito
    function updateCartCount() {
        $.ajax({
            url: '/api/cart/count',
            method: 'GET',
            success: function(data) {
                if (data.count > 0) {
                    $('#cart-count').text(data.count).removeClass('d-none');
                } else {
                    $('#cart-count').addClass('d-none');
                }
            }
        });
    }
    
    // Llamar a updateCartCount si el elemento existe
    if ($('#cart-count').length) {
        updateCartCount();
        setInterval(updateCartCount, 30000); // Actualizar cada 30 segundos
    }
    
    // Manejar cantidad en carrito
    $('.quantity-btn').on('click', function(e) {
        e.preventDefault();
        var form = $(this).closest('form');
        $.ajax({
            url: form.attr('action'),
            method: form.attr('method'),
            data: form.serialize(),
            success: function() {
                location.reload();
            }
        });
    });
    
    // Toggle de tema claro/oscuro
    $('#theme-toggle').on('click', function() {
        var currentTheme = $('html').attr('data-bs-theme');
        var newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        $('html').attr('data-bs-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        $(this).find('i').toggleClass('fa-moon fa-sun');
    });
    
    // Cargar tema guardado
    var savedTheme = localStorage.getItem('theme');
    if (savedTheme) {
        $('html').attr('data-bs-theme', savedTheme);
        if (savedTheme === 'light') {
            $('#theme-toggle i').removeClass('fa-moon').addClass('fa-sun');
        }
    }
    
    // Smooth scroll para anclas
    $('a[href^="#"]').on('click', function(e) {
        if ($(this).attr('href') !== '#') {
            e.preventDefault();
            var target = $(this).attr('href');
            if ($(target).length) {
                $('html, body').animate({
                    scrollTop: $(target).offset().top - 70
                }, 500);
            }
        }
    });
    
    // Formatear precios
    $('.price').each(function() {
        var price = parseFloat($(this).text().replace(/[^\d.-]/g, ''));
        if (!isNaN(price)) {
            $(this).text('$' + price.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,'));
        }
    });
    
    // Mostrar/ocultar contraseña
    $('.toggle-password').on('click', function() {
        var input = $(this).closest('.input-group').find('input');
        var icon = $(this).find('i');
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            input.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });
});
