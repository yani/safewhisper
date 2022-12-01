var note = {
    id: null,
    passKey: null
};

// Document ready
$(function(){

    // Check for hashbang
    let url = window.location.href;
    if (url.indexOf("#") === -1) {
        $('#noteLoading').hide();
        $('#noteInvalid').show();
        return;
    }

    // Check if hashbang has note id and passkey
    let hashBang = url.substring(url.indexOf("#") + 1);
    if (hashBang.indexOf("!") === -1) {
        $('#noteLoading').hide();
        $('#noteInvalid').show();
        return;
    }

    // Check if note id and passkey are valid
    let parts = hashBang.split('!');
    if(typeof parts[0] === 'undefined' || typeof parts[1] === 'undefined' || parts[0].length != store.note_id_length || parts[1].length != store.pass_length) {
        $('#noteLoading').hide();
        $('#noteInvalid').show();
        return;
    }

    note.id = parts[0];
    note.passKey = parts[1];

    // Show read and destroy confirmation
    $('#noteLoading').hide();
    $('#noteReadConfirm').show();
    $('.note-id-placeholder').text(note.id);

    // Note read confirmation
    $('#noteRead').on('click', function(e){

        // Show loading text
        $('#noteReadConfirm').hide();
        $('#noteLoading').show();

        // Get the note
        $.post('/note/read', {
            'id': note.id
        }, 'json').fail(function(){
            alert('Something went wrong');
        }).done(function(data){

            // Check if note retrieval is succesful
            if(!data.success){
                $('#noteLoading').hide();
                $('#noteFail').show();
                return;
            }

            // Decrypt note
            let decryptedBytes = CryptoJS.AES.decrypt(data.contents, note.passKey);
            let plaintext = decryptedBytes.toString(CryptoJS.enc.Utf8);

            // Show note
            $('#noteLoading').hide();
            $('#noteContents').text(plaintext);
            $('#note').show();
        });
    });
});
