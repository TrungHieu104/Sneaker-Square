function removeEmailVerifiedAt() {
    $.ajax({
        type: 'POST',
        url: '/remove-token',
        success: function(response) {
            // console.log(response.message);
        },
        error: function(xhr, status, error) {
            console.error('Error:', error);
        }
    });
}

setInterval(removeEmailVerifiedAt, 180000);

