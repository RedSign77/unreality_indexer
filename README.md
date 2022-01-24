# Unreality Useful Tools for Magento 2

### CLI command for Partial Reindex

```bin/magento indexer:mview:reindex {index_id} {environment emulation (optional)} ```

 - index_id one of view index ids
 - environment emulation is optional and represents one of the following: frontend, adminhtml, crontab

### CLI command for remove unused Product Attribute Options

```bin/magento catalog:product:attribute:options:cleanup```

 - List the unused Product Attribute Options in a table
 - You can choose to remove all unused attributes

## Requirements

 - Magento 2.3+, 2.4+
