# hidvl-pnxs

#### Install

You need https://getcomposer.org/doc/00-intro.md#locally to install dependencies. Once composer is isntalled, run `composer install`.

#### ENV

Must create `.env` with the variables:

```
APP_ROOT=/
media_service=
bobcat_url=
pnxs_service=
primo_service=
pnxs_query_prefix=hidvl
pnxs_search_field=lsr12
```

#### Test drive

If you have PHP in your system, you can run the built-in web server. E.g., `php --server localhost:5000`. 

#### Test URL

Open the URL http://localhost:5000/hidvl/47d7wmjw and you should see a video embeded using `iframe` and the title and other metadata requested from PNXS.
