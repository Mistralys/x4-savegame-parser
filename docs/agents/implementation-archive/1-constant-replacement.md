# Config naming adjustments

The current configuration key naming must be adjusted to
be more user friendly. 

## Naming Changes

- X4_FOLDER > gameFolder
- X4_STORAGE_FOLDER > storageFolder
- X4_SERVER_HOST > viewerHost
- X4_SERVER_PORT > viewerPort
- X4_MONITOR_AUTO_BACKUP > autoBackupEnabled
- X4_MONITOR_KEEP_XML > keepXMLFiles
- X4_MONITOR_LOGGING > loggingEnabled

## Utility Methods

Each of the settings must have a dedicated
method in the `Config` class to access them.

For example:

- gameFolder > getGameFolder()
- autoBackupEnabled > isAutoBackupEnabled()
