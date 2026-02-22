/**
 * PortPos Gateway - Frontend Scripts
 * Handles the iframe popup/overlay for the popup integration method.
 */
(function ($) {
    'use strict';

    var PortPosGateway = {
        modal: null,
        iframe: null,
        overlay: null,

        init: function () {
            PortPosGateway.buildModal();
        },

        buildModal: function () {
            // Create overlay
            var overlay = $('<div>', {
                id: 'edd-portpos-overlay',
                css: {
                    display: 'none',
                    position: 'fixed',
                    top: 0, left: 0, right: 0, bottom: 0,
                    background: 'rgba(0, 0, 0, 0.65)',
                    zIndex: 999998,
                    backdropFilter: 'blur(4px)'
                }
            });

            // Create modal container
            var modal = $('<div>', {
                id: 'edd-portpos-modal',
                css: {
                    display: 'none',
                    position: 'fixed',
                    top: '50%',
                    left: '50%',
                    transform: 'translate(-50%, -50%)',
                    width: '95%',
                    maxWidth: '780px',
                    height: '90vh',
                    background: '#fff',
                    borderRadius: '12px',
                    overflow: 'hidden',
                    zIndex: 999999,
                    boxShadow: '0 25px 60px rgba(0,0,0,0.35)',
                    display: 'flex',
                    flexDirection: 'column'
                }
            });

            // Modal header
            var header = $('<div>', {
                css: {
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'space-between',
                    padding: '14px 20px',
                    background: '#1a1a2e',
                    color: '#fff',
                    flexShrink: 0
                }
            });

            var brand = $('<span>', {
                text: edd_portpos_params.i18n.paying_via,
                css: { fontSize: '15px', fontWeight: '600', letterSpacing: '0.3px' }
            });

            var closeBtn = $('<button>', {
                type: 'button',
                id: 'edd-portpos-close',
                html: '&times;',
                css: {
                    background: 'rgba(255,255,255,0.15)',
                    border: 'none',
                    color: '#fff',
                    fontSize: '22px',
                    lineHeight: '1',
                    padding: '2px 10px',
                    borderRadius: '6px',
                    cursor: 'pointer'
                }
            });

            closeBtn.on('click', function () {
                PortPosGateway.closeModal();
            });

            header.append(brand, closeBtn);

            // Loading indicator
            var loader = $('<div>', {
                id: 'edd-portpos-loader',
                css: {
                    position: 'absolute',
                    top: '55px',
                    left: 0,
                    right: 0,
                    display: 'flex',
                    flexDirection: 'column',
                    alignItems: 'center',
                    justifyContent: 'center',
                    padding: '40px',
                    color: '#555',
                    fontSize: '14px',
                    gap: '14px'
                }
            });

            var spinner = $('<div>', {
                css: {
                    width: '42px',
                    height: '42px',
                    border: '4px solid #e0e0e0',
                    borderTopColor: '#1a1a2e',
                    borderRadius: '50%',
                    animation: 'edd-portpos-spin 0.8s linear infinite'
                }
            });

            loader.append(spinner, $('<span>', { text: edd_portpos_params.i18n.loading }));

            // Iframe
            var iframe = $('<iframe>', {
                id: 'edd-portpos-iframe',
                src: 'about:blank',
                allowTransparency: 'true',
                scrolling: 'yes',
                css: {
                    width: '100%',
                    flex: 1,
                    border: 'none',
                    display: 'block'
                }
            });

            iframe.on('load', function () {
                try {
                    var iframeSrc = this.contentWindow.location.href;
                    if (iframeSrc !== 'about:blank') {
                        loader.hide();
                    }
                } catch (e) {
                    loader.hide(); // Cross-origin: payment is loading
                }
            });

            modal.append(header, loader, iframe);
            $('body').append(overlay, modal);

            PortPosGateway.modal = modal;
            PortPosGateway.iframe = iframe;
            PortPosGateway.overlay = overlay;

            // Close on overlay click
            overlay.on('click', function () {
                PortPosGateway.closeModal();
            });

            // ESC key close
            $(document).on('keydown', function (e) {
                if (e.key === 'Escape') {
                    PortPosGateway.closeModal();
                }
            });
        },

        openModal: function (url) {
            // Inject keyframe animation for spinner
            if (!$('#edd-portpos-styles').length) {
                $('<style>', {
                    id: 'edd-portpos-styles',
                    text: '@keyframes edd-portpos-spin { 0%{transform:rotate(0deg)} 100%{transform:rotate(360deg)} }'
                }).appendTo('head');
            }

            PortPosGateway.iframe.attr('src', url);
            PortPosGateway.overlay.fadeIn(200);
            PortPosGateway.modal.css({ display: 'flex' }).hide().fadeIn(250);
            $('body').css('overflow', 'hidden');
        },

        closeModal: function () {
            PortPosGateway.iframe.attr('src', 'about:blank');
            PortPosGateway.overlay.fadeOut(200);
            PortPosGateway.modal.fadeOut(200);
            $('body').css('overflow', '');
            $('#edd-portpos-loader').show();
        }
    };

    // Initialize on DOM ready
    $(document).ready(function () {
        // Check if auto-open URL is present (set by PHP session after process_payment)
        if (typeof edd_portpos_params !== 'undefined' && edd_portpos_params.popup_url) {
            PortPosGateway.init();
            PortPosGateway.openModal(edd_portpos_params.popup_url);
        }
    });

    // Expose globally for programmatic access
    window.EDD_PortPos = PortPosGateway;

}(jQuery));
