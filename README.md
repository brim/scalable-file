Overview
========
The Scalable File cache backend for Magento backs the file backend with the database backend.  The file backend slows to a crawl with certain operations when the cache gets large. ie: cleaning by tags.  To solve this problem this cache backend saves tags and other metadata to the database.  So when cleaning by tags the database is queried for the affected cache ids.  When loading by cache id database queries are not done, so loading times are not negatively affected.

How To Use
==========
1. Copy this library into your Magento root
2. Open app/etc/local.xml
3. Set config > global > cache > backend = Brim_Cache_Backend_File_Scalable  
4. Save app/etc/local.xml
5. You're now using the Scalable File cache backend


  

