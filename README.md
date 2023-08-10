# News Project

This codebase is a demonstration of my PHP capabilities, specifically within the Craft CMS / Yii frameworks.

I have focused on the back-end component of this project. My progress so far:

-   [x] Create API proxy, specifically for The Guardian, but scaffolded for multiple providers
-   [x] Normalise returned content into a consistent model
-   [x] Apply data caching to improve performance of repeat requests

Next steps / considerations:

-   [ ] Under what circumstances (beyond expiry) should the cache be cleared (live blogs?)
-   [ ] Pagination? How does this work with multiple end points?
-   [ ] Storage of pinned articles: use session storage for guests, convert to user field?
-   [ ] Allow front-end registration / login / forgot password.
-   [ ] Do we want to store article metadata in the CMS? If so, we could use the [Wishlist plugin](https://verbb.io/craft-plugins/wishlist/features).
-   [ ] Are articles ever removed from an API? How does this affect pinned articles?
-   [ ] Do we ever need to return the full article and display it?
-   [ ] There are [so many news APIs](https://en.wikipedia.org/wiki/List_of_news_media_APIs)!

This project provided the opportunity to dig into:

-   🔌 Modules! They're [like plugins, but a bit different](https://verbb.io/blog/everything-you-need-to-know-about-modules)
-   🪵 Logging! Now I know how to register my own log file, which is tidy.
-   🌆 Models! Craft intends you to create models and records in tandem, but they're useful on their own.
-   🧳 Caching! I'd used this before, but it's good to practice.
-   🆎 Types! I'm learning TypeScript, and I have a better understanding of how to type my PHP.

## Using this Craft CMS project in local dev

1. Have [Docker Desktop](https://www.docker.com/products/docker-desktop/) installed
2. Clone your newly created repo down to your computer
3. `cd` to your repo in your terminal
4. Get the project up and running with `make dev`
5. Wait until you see output like this, and then access the site via the credentials that are output on the console:

```
spin-up-craft-queue-1  | ### Your Craft site is ready!
spin-up-craft-queue-1  | Frontend URL: http://localhost:8050/
spin-up-craft-queue-1  | CP URL: http://localhost:8050/admin
spin-up-craft-queue-1  | CP User: admin
spin-up-craft-queue-1  | CP Password: project
```

Hit `Control-C` to terminate the project and spin down the containers

The first time you start up your project, it'll take some time to set everything up. However, subsequent startups will be very quick.

[Visit the news controller](http://localhost:8050/actions/site-module/news?q=matildas) to see the returned data!

## Available `make` commands

This project uses `make` to execute various commands in the appropriate containers. Here's a list of available commands:

-   `make dev` - Start the dev server
-   `make composer xxx` - Execute a composer command in the PHP container
-   `make craft xxx` - Execute a `craft` CLI command in the PHP container
-   `make ssh` - Open up a shell in the PHP container
-   `make db-admin-reset` - Reset the admin (user with the `ID=1`) to the defaults in from the `.env` file; useful after importing a foreign database
-   `make db-export` - Clean the `db-seed/` directory and export the database to it
-   `make db-import` - Import the db from `db-seed/` directory into the mysql container

If the project is already running via `make dev` you can use a second terminal tab/window to execute additional commands.

## Random notes

-   The `.env` file is created by copying `example.env` file when you start the project up
-   The server will use the `INITIAL_SERVER_PORT` in the `.env` file for the initial port to start looking for unused ports from. It will increment it until it finds and unused port, and then use it
-   If instead you want to used a fixed port, you can explicitly set the `DEV_SERVER_PORT` in the `.env` file
-   The Docker containers will be named after the project directory, so give it a unique name for each project

## Credits

Forked from [Spin Up Craft](https://github.com/nystudio107/spin-up-craft) by [nystudio107](https://nystudio107.com/)
