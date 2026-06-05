# Math.Lib

Documentacion tecnica de la libreria `Math.Lib`, desarrollada para la sesion de laboratorio de pruebas unitarias con MSTest.

## Contenido

- API de la clase `Rooter`.
- Documentacion de atributos, metodos, parametros, valores de retorno y excepciones.
- Referencia generada automaticamente desde comentarios XML con DocFX.

## Uso basico

```csharp
using Math.Lib;

Rooter rooter = new();
double result = rooter.SquareRoot(4);
```

El metodo `SquareRoot` acepta solo valores positivos. Si recibe cero o un numero negativo, lanza una excepcion `ArgumentOutOfRangeException`.
