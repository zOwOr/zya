<script>
    function previewImage(input) {
        const container = input.closest('.image-container');
        const imagePreview = container.querySelector('.image-preview');
        const file = input.files[0];

        if (!file) return;

        // Eliminar cualquier embed de PDF previo
        const existingEmbed = container.querySelector('.pdf-preview-embed');
        if (existingEmbed) existingEmbed.remove();

        if (file.type === 'application/pdf') {
            // Para PDFs: ocultar el img y mostrar un embed/iframe
            imagePreview.style.display = 'none';
            const url = URL.createObjectURL(file);
            const embed = document.createElement('embed');
            embed.src = url;
            embed.type = 'application/pdf';
            embed.className = 'pdf-preview-embed';
            embed.style.cssText = 'width:100%; height:200px; border:1px solid #ccc; border-radius:6px; margin-top:6px;';
            container.appendChild(embed);
        } else {
            // Para imágenes: usar FileReader
            imagePreview.style.display = 'block';
            imagePreview.src = URL.createObjectURL(file); // Más rápido que FileReader para previsualización simple
        }
    }

    function previewVideo() {
        var file = document.getElementById('video').files[0];
        var videoElement = document.getElementById('videoPreview');
        
        // Verifica si el archivo es un video válido
        if (file && file.type.startsWith('video')) {
            var url = URL.createObjectURL(file); // Crea un enlace temporal para la vista previa del video
            videoElement.src = url;
            videoElement.style.display = 'block'; // Muestra el video
        } else {
            videoElement.style.display = 'none'; // Oculta el video si no es válido
        }
    }
</script>
