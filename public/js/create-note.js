// Document ready
$(function(){

    // Disable create button when note is empty
    $('#note').on('change keyup paste', function(e){
        if($(this).val().length > 0){
            $('#createNote').prop('disabled', false);
        } else {
            $('#createNote').prop('disabled', true);
        }
    });

    // Create a note
    $('#createNote').on('click', function(e){

        $(this).buttonLoader(true);
        $('#note').prop('disabled', true);

        // Create PASSKEY
        var passKey          = '';
        let characters       = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        let charactersLength = characters.length;
        for (let i = 0; i < store.pass_length; i++) {
            passKey += characters.charAt(Math.floor(Math.random() * charactersLength));
        }

        // Encrypt the note
        var contents  = $('#note').val();
        var encrypted = CryptoJS.AES.encrypt(contents, passKey).toString();

        // Send encrypted note to server
        $.post('/note/create', {
            'contents': encrypted
        }, 'json').fail(function(){

            alert('Something went wrong');

        }).done(function(data){

            // Check if note creation is succesful
            if(!data.success){
                alert('Something went wrong');
                return;
            }

            // Get URL for note (with decryption key)
            var url = window.location.href;
            url += 'note#' + data.id + '!' + passKey;

            // Show "note is made" page
            $('#noteURL').val(url);
            $('#noteID').text(data.id);
            $('#createNotePage').hide();
            $('#noteCreatedPage').show();

            // Copy to clipboard button
            new ClipboardJS('#copyNoteURL')
                .on('success', function(e) {
                    e.clearSelection();

                    // Update copy button
                    $('#copyNoteURL').addClass('btn-success');
                    $('#copyNoteURL').text("Copied!");
                });
        })

    });

})
