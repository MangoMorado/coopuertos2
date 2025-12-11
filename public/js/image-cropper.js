// Image Cropper para conductores
document.addEventListener('DOMContentLoaded', function() {
    const fotoInput = document.getElementById('foto-input');
    const cropperModal = document.getElementById('cropper-modal');
    const cropperImage = document.getElementById('cropper-image');
    const cropperContainer = document.getElementById('cropper-container');
    const cancelCropBtn = document.getElementById('cancel-crop');
    const cropBtn = document.getElementById('crop-btn');
    const previewContainer = document.getElementById('preview-container');
    const previewImage = document.getElementById('preview-image');
    let cropper = null;

    if (!fotoInput) return;

    fotoInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                cropperImage.src = event.target.result;
                cropperModal.classList.remove('hidden');
                
                // Inicializar Cropper con relación 1:1
                if (cropper) {
                    cropper.destroy();
                }
                
                cropper = new Cropper(cropperImage, {
                    aspectRatio: 1,
                    viewMode: 1,
                    dragMode: 'move',
                    autoCropArea: 0.8,
                    restore: false,
                    guides: true,
                    center: true,
                    highlight: false,
                    cropBoxMovable: true,
                    cropBoxResizable: true,
                    toggleable: false,
                    minCropBoxWidth: 100,
                    minCropBoxHeight: 100,
                });
            };
            reader.readAsDataURL(file);
        }
    });

    cancelCropBtn.addEventListener('click', function() {
        cropperModal.classList.add('hidden');
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }
        fotoInput.value = '';
        if (previewContainer) {
            previewContainer.classList.add('hidden');
        }
    });

    cropBtn.addEventListener('click', function() {
        if (cropper) {
            const canvas = cropper.getCroppedCanvas({
                width: 400,
                height: 400,
                imageSmoothingEnabled: true,
                imageSmoothingQuality: 'high',
            });

            canvas.toBlob(function(blob) {
                const file = new File([blob], 'cropped-image.jpg', { type: 'image/jpeg' });
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                fotoInput.files = dataTransfer.files;

                // Mostrar preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    if (previewImage) {
                        previewImage.src = e.target.result;
                        previewContainer.classList.remove('hidden');
                    }
                };
                reader.readAsDataURL(blob);

                // Ocultar modal
                cropperModal.classList.add('hidden');
                if (cropper) {
                    cropper.destroy();
                    cropper = null;
                }
            }, 'image/jpeg', 0.9);
        }
    });
});

