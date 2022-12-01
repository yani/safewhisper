SafeWhisper
===========

SafeWhisper is a self-hosted microservice for sharing encrypted notes.

Notes are encrypted with AES using the crypto-js library.
Encryption happens client-side (in the browser of the user) and the server only receives the encrypted note.
The decryption key is added to the generated URL as a fragment identifier and is not shared with the server.

The server uses a Redis data store for storing the encrypted notes instead of a traditional database or filesystem.
This means that the encrypted notes are stored in RAM and never touch the physical harddrive.
This also means that if the server loses power or is manually rebooted, all notes are lost.

The only data that is stored is the encrypted note and the note identifier.
Identifiable data such as IP addresses are not stored.
SafeWhisper is also designed to not leak any information to the default access_log.

**An official instance is hosted at:** https://safewhisper.net/


## Docker install

_coming soon_


## Native install

If you wish to setup a non-containerized instance, you can do a [native install](/docs/native-install.md).


## Libraries

The 3rd party libraries that have been used can be found in [docs/libs.md](/docs/libs.md).


## License

[GPL-3.0](/LICENSE).
