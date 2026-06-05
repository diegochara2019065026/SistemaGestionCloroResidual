# MyMath - Pruebas unitarias con MSTest

Proyecto de laboratorio para practicar pruebas unitarias con MSTest, cobertura de codigo, documentacion con DocFX y publicacion de paquetes NuGet.

## Estructura

```text
MyMath/
|-- MyMath.sln
|-- Math.Lib/
|   |-- Math.Lib.csproj
|   `-- Rooter.cs
|-- Math.Tests/
|   |-- Math.Tests.csproj
|   `-- RooterTests.cs
`-- docs/
    |-- docfx.json
    `-- index.md
```

## Restaurar, compilar y probar

```bash
dotnet restore
dotnet build
dotnet test --collect:"XPlat Code Coverage"
```

## Generar reporte de cobertura

```bash
dotnet tool install -g dotnet-reportgenerator-globaltool
reportgenerator "-reports:./Math.Tests/TestResults/**/coverage.cobertura.xml" "-targetdir:Cobertura" "-reporttypes:Html"
```

El reporte queda disponible en:

```text
Cobertura/index.html
```

## Generar documentacion con DocFX

```bash
dotnet tool install -g docfx
docfx docs/docfx.json
```

La documentacion queda disponible en:

```text
docs/_site/index.html
```

## Publicacion automatica

El repositorio incluye tres workflows:

- `.github/workflows/publish_docs.yml`: genera documentacion DocFX y la publica en GitHub Pages dentro de `docs/`.
- `.github/workflows/publish_cov_report.yml`: ejecuta pruebas, genera cobertura HTML y la publica en GitHub Pages dentro de `coverage/`.
- `.github/workflows/release.yml`: compila, prueba, genera el paquete NuGet con version `2019065026.0.0`, lo publica en GitHub Packages y crea un release.

## Caso de excepcion requerido

La clase `Rooter` lanza `ArgumentOutOfRangeException` cuando se envia un valor menor o igual a cero. El mensaje validado por prueba es:

```text
El valor ingresado es invalido, solo se puede ingresar números positivos
```

En el codigo fuente se usa una secuencia Unicode para mantener el texto con tilde en tiempo de ejecucion.
