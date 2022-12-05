SafeWhisper: Technical analysis and encryption validation
=========================================================

In this document the technical workings of SafeWhisper are explained.

Notes are encrypted with [AES]() using the [crypto-js]() library.
Encryption happens client-side (in the browser of the user) and the server only receives the encrypted note.
The decryption key is added to the generated URL as a [fragment identifier]() and is not shared with the server.

The server uses a [Redis data store]() for storing the encrypted notes instead of a traditional database or filesystem.
This means that the encrypted notes are stored in RAM and never touch the physical harddrive.
This also means that if the server loses power or is manually rebooted, all notes are lost.
The only data that is stored is the encrypted note and its identifier.

Identifiable data such as IP addresses are not stored.
If a hosted SafeWhisper instance is configured to log standard web access requests,
note identifiers should not be logged as they are exchanged using the POST body.
<!-- The official docker image is configured to not log any requests. -->



## Validating

You can validate the encryption of a SafeWhisper instance by getting a trusted version of [crypto-js]() and then doing the same encryption process locally:

1. Go to the SafeWhisper instance you want to validate and open up your browser **dev-tools** (F12).
2. Create a note with your desired contents.
3. Look for the **pass-key** in the generated URL: `/note#<note-id>!<pass-key>`.
4. Look for the encrypted note which has been sent to the `/note/create` endpoint (visible using **dev-tools**).
5. Now use your trusted **crypto-js** version and execute the encryption code:
```js
CryptoJS.AES.encrypt('<note-contents>', '<pass-key>').toString();
```
6. Compare your encrypted note to the note that was sent to the endpoint of the SafeWhisper instance, and make sure the **pass-key** was never sent to the server.
7. Keep your **dev-tools** open and read the note using the instance's generated URL.
8. If the **pass-key** was never sent to the server, we should be all good.

**NOTE:** A SafeWhisper instance can have its code altered to not destruct the notes after they have been read.
That's why the source is public and you can host your own trusted instances! 🎉

