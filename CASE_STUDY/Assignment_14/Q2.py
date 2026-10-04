# Case Study 2: Student Grade Analysis

import pandas as pd
import statistics as stats
import matplotlib.pyplot as plt

# 1. Read the scores data from CSV file
df = pd.read_csv("scores.csv")

print("Student Scores:")
print(df)

# Convert Score column into a list
scores = df["Score"].tolist()

# 2. Calculate mean, median and mode
mean = stats.mean(scores)
median = stats.median(scores)
mode = stats.multimode(scores)

print("\nMean:", mean)
print("Median:", median)
print("Mode:", mode)

# 3. Calculate range, standard deviation and coefficient of variation
data_range = max(scores) - min(scores)
standard_deviation = stats.stdev(scores)
coefficient_of_variation = (standard_deviation / mean) * 100

print("\nRange:", data_range)
print("Standard Deviation:", standard_deviation)
print("Coefficient of Variation:", coefficient_of_variation, "%")

# 4. Calculate quartiles
quartiles = stats.quantiles(scores, n=4)

Q1 = quartiles[0]
Q2 = quartiles[1]
Q3 = quartiles[2]

print("\nQuartiles:")
print("Q1:", Q1)
print("Q2:", Q2)
print("Q3:", Q3)

# Draw boxplot
plt.boxplot(scores)
plt.title("Student Score Boxplot")
plt.ylabel("Score")
plt.show()

# 5. Find number of students scoring above the mean
above_mean = sum(score > mean for score in scores)

print("Number of students scoring above the mean:", above_mean)