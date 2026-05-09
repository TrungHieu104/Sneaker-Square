var fileInput = document.querySelector('.inputFile'); // Chọn theo class

if (fileInput) {
    fileInput.addEventListener('change', function () {
        var previewImage = document.getElementById('previewImage');
        var file = this.files[0];
        if (file) {
            var reader = new FileReader();
    
            reader.onload = function (e) {
                previewImage.src = e.target.result;
            };
    
            reader.readAsDataURL(file);
        }
    });
}