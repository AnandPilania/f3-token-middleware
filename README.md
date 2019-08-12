# f3-token-middleware

Just pass your `token secure` routes [`/` OR '/secure/*'] & `handler` to `f3-token-middleware` & relax... it will check requests `pattern` + `token` & execute your `handler` if token not found.

**NOTE:** This package built/ported from [ikkez/f3-middleware](https://github.com/ikkez/f3-middleware).

## Install

      `composer require anandpilania/f3-token-middleware`


## Usage

- 1: Configure `f3`:

      `$f3->mset(array(
      
          'TOKEN' => array(
          
              'KEY' => 'Authorization', // HEADER KEY
              
              'STARTS_WITH' => 'X-Auth-Token', // HEADER KEY -> "Authorization: X-Auth-Token xxxxxxxx"
              
              'TABLE' => 'Models\Token', // FQCN
              
              'TABLE_KEY' => 'token' // KEY, which you used to store the token value in table
              
          )
          
      ));`
      
- 2: Initialize in your main `bootstrap` file:

      `$tokenMiddleware = new TokenMiddleware();`
      
      
- 3: `Protect` routes/pattern:

      `$tokenMiddleware->protect(array('GET|POST|PUT|DELETE /home/*', 'POST /profile'), function($f3, $params, $alias) {
      
          // YOUR FUNCTION, IF 'TOKEN' NOT SUPPLIED
          
      });`
      
      
- 4: RUN:

      `$tokenMiddleware->run();`
