using System;

namespace Math.Lib
{
    /// <summary>
    /// Provides operations for calculating square roots.
    /// </summary>
    public class Rooter
    {
        /// <summary>
        /// Calculates the square root of a positive number by using Newton's method.
        /// </summary>
        /// <param name="input">Positive number whose square root will be calculated.</param>
        /// <returns>The approximate square root of <paramref name="input" />.</returns>
        /// <exception cref="ArgumentOutOfRangeException">
        /// Thrown when <paramref name="input" /> is less than or equal to zero.
        /// </exception>
        public double SquareRoot(double input)
        {
            if (input <= 0.0)
            {
                throw new ArgumentOutOfRangeException(
                    nameof(input),
                    "El valor ingresado es invalido, solo se puede ingresar n\u00FAmeros positivos");
            }

            double result = input;
            double previousResult = -input;

            while (System.Math.Abs(previousResult - result) > result / 1000)
            {
                previousResult = result;
                result -= (result * result - input) / (2 * result);
            }

            return result;
        }
    }
}
