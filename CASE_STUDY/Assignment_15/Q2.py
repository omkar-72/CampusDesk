# Assignment No. 15
# Case Study 2: Website Traffic Analysis

import pandas as pd
import matplotlib.pyplot as plt

# Read data from CSV file
df = pd.read_csv("website_traffic.csv")

print("Website Traffic Data:")
print(df)

# 1. Line graph - Daily website traffic over a month
plt.figure(figsize=(10, 5))
plt.plot(df["Day"], df["Traffic"], marker="o")

plt.title("Daily Website Traffic")
plt.xlabel("Day")
plt.ylabel("Number of Visitors")
plt.grid()
plt.show()


# 2. Bar chart - Average time on site for different traffic sources
source_time = df.groupby("Source")["Time_On_Site"].mean()

plt.figure(figsize=(8, 5))
plt.bar(source_time.index, source_time.values)

plt.title("Average Time on Site by Traffic Source")
plt.xlabel("Traffic Source")
plt.ylabel("Average Time (minutes)")
plt.show()


# 3. Pie chart - Proportion of traffic from different sources
source_traffic = df.groupby("Source")["Traffic"].sum()

plt.figure(figsize=(7, 7))
plt.pie(
    source_traffic,
    labels=source_traffic.index,
    autopct="%1.1f%%"
)

plt.title("Traffic by Source")
plt.show()


# 4. Tree-map - Distribution of traffic across website pages

import squarify

page_traffic = df.groupby("Page")["Traffic"].sum()

plt.figure(figsize=(10, 6))

squarify.plot(
    sizes=page_traffic.values,
    label=page_traffic.index,
    alpha=0.8
)

plt.title("Website Traffic Distribution by Page")
plt.axis("off")
plt.show()