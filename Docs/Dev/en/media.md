# Media

A media element is used in order to handle files, directories, virtual directories and other resources (e.g. links, external resource such as dropbox etc.).

## Path

### File system

The path is either the absolute path on the file system or relative path in relation to applications path. If the path is an absolute path the media element also needs to be set to `isAbsolute() === true`. The path includes the file itself.

## Virtual Path

The virtual path is the virtual location where it should show up in the media module. This makes it possible to change the visual location without changing the physical storage location. Additionally, this makes it also possible to reference the same physical file from different locations. Think about it similar to `symlink`

## Absolute

Is the file path an absolute file path or a relative file path (relative to the application path).

## Extensions

The extension can be one of the following two:

1. Extension of the file
2. `collection` if it is a virtual directory/collection