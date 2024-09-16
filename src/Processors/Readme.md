# Custom Swarrot Processors

**FatalErrorNack**

`FatalErrorNack` is a swarrot processor that will nack the message if a fatal run-time errors occurs (E_ERROR).

For example : 
```
PHP Fatal error:  Allowed memory size of XXX bytes exhausted (tried to allocate YYY bytes) in [...]
```
