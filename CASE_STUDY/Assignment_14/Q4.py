# Case Study 4: Real Estate Pricing

import pandas as pd
import statistics as stats
import matplotlib.pyplot as plt

# 1. Read house price data from CSV file
df = pd.read_csv("house_prices.csv")

print("House Price Data:")
print(df)

# Convert Price column into a list
prices = df["Price"].tolist()

# 2. Calculate mean, median and mode
mean = stats.mean(prices)
median = stats.median(prices)
mode = stats.multimode(prices)

print("\nMean Price:", mean)
print("Median Price:", median)
print("Mode Price:", mode)

# 3. Calculate standard deviation and variance
standard_deviation = stats.stdev(prices)
variance = stats.variance(prices)

print("Standard Deviation:", standard_deviation)
print("Variance:", variance)

# 4. Calculate range
data_range = max(prices) - min(prices)

print("Range:", data_range)

# 5. Calculate quartiles and IQR
quartiles = stats.quantiles(prices, n=4)

Q1 = quartiles[0]
Q2 = quartiles[1]
Q3 = quartiles[2]

IQR = Q3 - Q1

print("\nQuartiles:")
print("Q1:", Q1)
print("Q2:", Q2)
print("Q3:", Q3)
print("IQR:", IQR)

# 6. Identify outliers using IQR method
lower_limit = Q1 - 1.5 * IQR
upper_limit = Q3 + 1.5 * IQR

outliers = [x for x in prices if x < lower_limit or x > upper_limit]

print("\nOutliers:", outliers)

# 7. Draw boxplot
plt.boxplot(prices)
plt.title("Real Estate Price Distribution")
plt.ylabel("House Price")
plt.show()