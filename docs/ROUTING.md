# Routing (Radix Framework)

## Översikt
Den här guiden beskriver hur routing och dispatch fungerar i Radix Framework.

## Var ligger koden?
- `routes/` (där du definierar rutter)
- Router/Dispatcher (kärnan som matchar och kör handlers)
- Controllers (t.ex. `src/Controllers/` eller `app/Controllers/` beroende på projekt)

## Grundbegrepp
- **Route**: en matchning mellan HTTP-metod + path och en handler
- **Handler**: antingen en callable (closure/funktion) eller en controller + metod
- **Params**: route-parametrar som skickas vidare till handlern
- **Middleware**: körs runt handlern (pipeline)

## Exempel (pseudo)


php // GET /home -> HomeController::index // GET /api/v1/users/{id} -> UserController::show($id) 

## Dispatcher: hur en request blir ett svar
1) Matcha route för requestens path + method
2) Bygg argumentlista till handlern (request/response/params)
3) Bygg middleware-lista (om route har middleware)
4) Kör pipeline → kör handler → returnera Response

## Vanliga problem
- 404 / “No route matched”: fel path, metod eller route saknas
- Argument-mismatch: handlerns argument matchar inte route-parametrar
- Middleware alias saknas: middleware-alias inte registrerat i middleware-map

## Checklista vid felsökning
- Är metoden rätt? (GET/POST/PUT/DELETE)
- Är path rätt? (inkl. prefix som `/api/v1`)
- Finns routen registrerad?
- Tar handlern emot rätt parametrar?

