# Templates / Views (Radix Framework)

## Översikt
Den här guiden beskriver template-systemet:
- templates i `views/`
- syntax (placeholders, directives)
- components + slots
- extends/includes
- cache (views cache path) och invalidation

## Var ligger koden?
- Templates: `views/`
- Komponenter: `views/components/`
- TemplateViewer: (t.ex. `framework/src/Viewer/...` eller motsvarande)

## Grundkoncept
- Rendera en template med data
- Globala data (“shared”)
- Filters (transformera data innan rendering)
- Cache i production, av i development

## Tips
- Testa template rendering med små fixtures
- Håll komponenter små
- Var försiktig med cache-path (ska inte peka på projektroten)