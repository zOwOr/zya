<script>
    function previewImage(input) {
        const container = input.closest('.image-container');
        const imagePreview = container.querySelector('.image-preview');

        imagePreview.style.display = 'block';

        const oFReader = new FileReader();
        oFReader.readAsDataURL(input.files[0]);

        oFReader.onload = function(oFREvent) {
            imagePreview.src = oFREvent.target.result;
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
