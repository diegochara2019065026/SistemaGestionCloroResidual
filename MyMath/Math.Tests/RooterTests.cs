using Math.Lib;
using Microsoft.VisualStudio.TestTools.UnitTesting;

namespace Math.Tests
{
    [TestClass]
    public class RooterTests
    {
        [TestMethod]
        public void BasicRooterTest()
        {
            Rooter rooter = new();
            double expectedResult = 2.0;
            double input = expectedResult * expectedResult;

            double actualResult = rooter.SquareRoot(input);

            Assert.AreEqual(expectedResult, actualResult, delta: expectedResult / 100);
        }

        [TestMethod]
        public void RooterValueRange()
        {
            Rooter rooter = new();

            for (double expected = 1e-8; expected < 1e+8; expected *= 3.2)
            {
                RooterOneValue(rooter, expected);
            }
        }

        [TestMethod]
        public void RooterTestNegativeInput()
        {
            Rooter rooter = new();

            Assert.ThrowsException<System.ArgumentOutOfRangeException>(() => rooter.SquareRoot(-10));
        }

        [TestMethod]
        public void RooterTestNegativeInputWithMessage()
        {
            Rooter rooter = new();

            System.ArgumentOutOfRangeException exception =
                Assert.ThrowsException<System.ArgumentOutOfRangeException>(() => rooter.SquareRoot(-10));

            StringAssert.Contains(
                exception.Message,
                "El valor ingresado es invalido, solo se puede ingresar n\u00FAmeros positivos");
        }

        private static void RooterOneValue(Rooter rooter, double expectedResult)
        {
            double input = expectedResult * expectedResult;
            double actualResult = rooter.SquareRoot(input);

            Assert.AreEqual(expectedResult, actualResult, delta: expectedResult / 1000);
        }
    }
}
