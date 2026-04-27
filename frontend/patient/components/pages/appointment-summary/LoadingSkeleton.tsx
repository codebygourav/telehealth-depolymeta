const LoadingSkeleton = () => {
  return (
    <div className="max-w-4xl mx-auto pb-12">
      {/* Header Skeleton */}
      <div className="flex items-center justify-between mb-12">
        <div className="space-y-2">
          <div className="h-10 w-64 bg-gray-200 rounded-lg animate-pulse" />
          <div className="h-4 w-48 bg-gray-200 rounded animate-pulse" />
        </div>
        <div className="h-8 w-32 bg-gray-200 rounded-full animate-pulse" />
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-12 gap-12">
        {/* Left Column Skeleton */}
        <div className="lg:col-span-7 space-y-8">
          <div className="bg-gray-100 rounded-[32px] p-8 animate-pulse">
            <div className="flex gap-6 mb-8">
              <div className="w-24 h-24 bg-gray-200 rounded-3xl" />
              <div className="flex-1 space-y-3">
                <div className="h-4 w-24 bg-gray-200 rounded" />
                <div className="h-8 w-48 bg-gray-200 rounded" />
                <div className="h-4 w-64 bg-gray-200 rounded" />
              </div>
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div className="h-20 bg-gray-200 rounded-2xl" />
              <div className="h-20 bg-gray-200 rounded-2xl" />
            </div>
          </div>
          <div className="h-40 bg-gray-100 rounded-3xl animate-pulse" />
        </div>

        {/* Right Column Skeleton */}
        <div className="lg:col-span-5">
          <div className="h-[500px] bg-gray-100 rounded-[40px] animate-pulse" />
        </div>
      </div>
    </div>
  );
};

export default LoadingSkeleton;