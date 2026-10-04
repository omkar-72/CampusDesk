# Case Study 1: Classic Marketing Analysis

import pandas as pd
import statistics as stats

# 1. Create the dataset
data = {
    "Date": ["01-09-2026", "02-09-2026", "03-09-2026", "04-09-2026",
             "05-09-2026", "06-09-2026", "07-09-2026"],
    "Sales": [1200, 1500, 1100, 1800, 1600, 2000, 1700],
    "Temperature": [28, 30, 27, 31, 29, 32, 30],
    "Satisfaction": [7, 8, 6, 9, 8, 9, 8]
}

df = pd.DataFrame(data)

# Display the dataset
print("Marketing Dataset:")
print(df)

# Describe the data
print("\nDescription of Data:")
print(df.describe())

# 2. Find the average temperature using statistics library
temperature = df["Temperature"].tolist()

average_temperature = stats.mean(temperature)

print("\nAverage Temperature:", average_temperature)

# 3. Find the total sales in a week
total_sales = sum(df["Sales"])

print("Total Sales in a Week:", total_sales)