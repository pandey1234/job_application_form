$(document).ready(function() {
    $("#jobApplicationForm").on("submit", function(event) {
        event.preventDefault();
        
        let formData = new FormData(this);
        
        $.ajax({
            url: "process.php",
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json', // Ensure the response is interpreted as JSON
            success: function(response) {
                console.log("Server response:", response);
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Application Submitted',
                        text: response.message
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX error:", xhr.responseText);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while submitting your application. Please check the console for more details.'
                });
            }
        });
    });
});

